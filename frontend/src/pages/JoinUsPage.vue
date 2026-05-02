<script setup lang="ts">
import { useForm } from 'vue-formify';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import RadioButton from 'primevue/radiobutton';
import Checkbox from 'primevue/checkbox';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import {
  MemberApplicationRequests,
  type MemberApplicationPayload,
} from '@/services/requests/MemberApplicationRequests';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();
const toast = useToast();

const EDUCATION_OPTIONS = ['BSc', 'MSc', 'PhD'] as const;
const LANGUAGE_OPTIONS = ['Hungarian', 'English', 'German'] as const;
const DEPARTMENT_OPTIONS = [
  'Marketing & Design',
  'Electronics & Software Development',
  'Propulsion',
  'Structure & Aerodynamics',
  'Management',
] as const;

interface FormState {
  email: string;
  name: string;
  university: string;
  education: string;
  faculty: string;
  why: string;
  hours: string;
  languages: string[];
  otherLanguage: string;
  department: string;
  tasks: string;
  skills: string;
}

const initialState: FormState = {
  email: '',
  name: '',
  university: '',
  education: '',
  faculty: '',
  why: '',
  hours: '',
  languages: [],
  otherLanguage: '',
  department: '',
  tasks: '',
  skills: '',
};

const { Form, values, reset } = useForm<FormState>({ initialValues: initialState });
const status = ref<'idle' | 'submitting' | 'success' | 'error'>('idle');

const plusKeys = [
  'join.plus.english',
  'join.plus.knowledge',
  'join.plus.hours',
  'join.plus.inPerson',
  'join.plus.tools',
  'join.plus.tdk',
] as const;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const onSubmit = async () => {
  status.value = 'submitting';

  const otherLanguage = (values.value.otherLanguage ?? '').trim();
  const languages = [
    ...(values.value.languages ?? []),
    ...(otherLanguage ? [`Other: ${otherLanguage}`] : []),
  ];

  const payload: MemberApplicationPayload = {
    email: values.value.email ?? '',
    name: values.value.name ?? '',
    university: values.value.university ?? '',
    education: values.value.education ?? '',
    faculty: values.value.faculty ?? '',
    why: values.value.why ?? '',
    hours: values.value.hours ?? '',
    languages,
    department: values.value.department ?? '',
    tasks: values.value.tasks ?? '',
    skills: values.value.skills ?? '',
  };

  try {
    await MemberApplicationRequests.submit(payload);
    status.value = 'success';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch {
    status.value = 'error';
    toast.add({
      severity: 'error',
      summary: t('join.error'),
      life: 4000,
    });
  }
};

const resetForm = () => {
  reset();
  status.value = 'idle';
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const submitting = computed(() => status.value === 'submitting');
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <Toast />
  <HtmlTitle :title="t('join.title')" />

  <div class="container join">
    <div class="join__back">
      <SectionButton to="/">{{ t('button.home') }}</SectionButton>
    </div>

    <template v-if="status === 'success'">
      <div class="join__success">
        <h2 class="join__success-title">{{ t('join.success.title') }}</h2>
        <p class="join__success-body">{{ t('join.success.body') }}</p>
        <SectionButton type="button" @click="resetForm">
          {{ t('join.success.again') }}
        </SectionButton>
      </div>
    </template>

    <template v-else>
      <div class="join__hero">
        <div class="join__hero-grid">
          <div>
            <span class="join__kicker">{{ t('join.tagline') }}</span>
            <h1 class="join__title">{{ t('join.title') }}</h1>
            <p class="join__intro">{{ t('join.intro') }}</p>
          </div>
          <div class="join__plus-card">
            <div class="join__plus-heading">{{ t('join.plus.heading') }}</div>
            <ul class="join__plus-list">
              <li v-for="key in plusKeys" :key="key" class="join__plus-item">
                <span class="join__plus-bullet">✓</span>
                <span>{{ t(key) }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <Form @submit="onSubmit">
        <div class="join__section">
          <div class="join__section-head">
            <span class="join__section-num">01</span>
            <h2 class="join__section-title">{{ t('join.section.about') }}</h2>
          </div>
          <div class="join__fields">
            <div class="join__field">
              <label>{{ t('join.email') }}</label>
              <InputText v-model="values.email" type="email" required class="w-full" />
            </div>
            <div class="join__row-2">
              <div class="join__field">
                <label>{{ t('join.name') }}</label>
                <InputText v-model="values.name" required class="w-full" />
              </div>
              <div class="join__field">
                <label>{{ t('join.university') }}</label>
                <InputText v-model="values.university" required class="w-full" />
              </div>
            </div>
            <div class="join__field">
              <label>{{ t('join.education') }}</label>
              <div class="join__option-grid">
                <label
                  v-for="opt in EDUCATION_OPTIONS"
                  :key="opt"
                  class="join__option-pill"
                  :data-checked="values.education === opt"
                >
                  <RadioButton v-model="values.education" :value="opt" />
                  <span>{{ opt }}</span>
                </label>
              </div>
            </div>
            <div class="join__field">
              <label>{{ t('join.faculty') }}</label>
              <InputText v-model="values.faculty" required class="w-full" />
            </div>
          </div>
        </div>

        <div class="join__section">
          <div class="join__section-head">
            <span class="join__section-num">02</span>
            <h2 class="join__section-title">{{ t('join.section.availability') }}</h2>
          </div>
          <div class="join__fields">
            <div class="join__field">
              <label>{{ t('join.why') }}</label>
              <Textarea v-model="values.why" rows="3" auto-resize required class="w-full" />
            </div>
            <div class="join__field">
              <label>{{ t('join.hours') }}</label>
              <small>{{ t('join.hoursHelp') }}</small>
              <InputText v-model="values.hours" required class="w-full" />
            </div>
            <div class="join__field">
              <label>{{ t('join.languages') }}</label>
              <div class="join__option-grid">
                <label
                  v-for="opt in LANGUAGE_OPTIONS"
                  :key="opt"
                  class="join__option-pill"
                  :data-checked="(values.languages ?? []).includes(opt)"
                >
                  <Checkbox v-model="values.languages" :value="opt" />
                  <span>{{ opt }}</span>
                </label>
              </div>
            </div>
            <div class="join__field">
              <label>{{ t('join.languagesOther') }}</label>
              <InputText
                v-model="values.otherLanguage"
                :placeholder="t('join.languagesOtherPlaceholder')"
                class="w-full"
              />
            </div>
          </div>
        </div>

        <div class="join__section">
          <div class="join__section-head">
            <span class="join__section-num">03</span>
            <h2 class="join__section-title">{{ t('join.section.contribution') }}</h2>
          </div>
          <div class="join__fields">
            <div class="join__field">
              <label>{{ t('join.department') }}</label>
              <div class="join__option-grid join__option-grid--col">
                <label
                  v-for="opt in DEPARTMENT_OPTIONS"
                  :key="opt"
                  class="join__option-pill"
                  :data-checked="values.department === opt"
                >
                  <RadioButton v-model="values.department" :value="opt" />
                  <span>{{ opt }}</span>
                </label>
              </div>
            </div>
            <div class="join__field">
              <label>{{ t('join.tasks') }}</label>
              <Textarea v-model="values.tasks" rows="3" auto-resize required class="w-full" />
            </div>
            <div class="join__field">
              <label>{{ t('join.skills') }}</label>
              <Textarea v-model="values.skills" rows="3" auto-resize required class="w-full" />
            </div>
          </div>
        </div>

        <div class="join__submit-row">
          <SectionButton to="/">{{ t('button.home') }}</SectionButton>
          <SectionButton type="submit" :disabled="submitting">
            {{ submitting ? t('join.submitting') : t('join.submit') }}
          </SectionButton>
        </div>
      </Form>
    </template>
  </div>
</template>

<style lang="scss" scoped>
@import 'breakpoints';

.join {
  padding-top: calc(var(--header-height) + 32px);
  padding-bottom: 64px;

  &__back {
    margin-bottom: 32px;
  }

  &__hero {
    position: relative;
    padding: clamp(28px, 4vw, 56px);
    margin-bottom: 32px;
    border: 1px solid var(--card-border);
    border-radius: 12px;
    background: var(--card-bg);
    overflow: hidden;
  }

  &__hero-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 32px;

    @include media-up(md) {
      grid-template-columns: minmax(0, 1.5fr) minmax(260px, 1fr);
      align-items: end;
    }
  }

  &__kicker {
    display: inline-block;
    padding: 4px 12px;
    margin-bottom: 16px;
    border-radius: 999px;
    color: var(--color-primary);
    background: rgb(242 172 60 / 18%);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }

  &__title {
    margin: 0 0 16px;
    color: var(--color-primary);
    font-size: clamp(40px, 8vw, 84px);
    line-height: 0.96;
    font-family: var(--font-family-headline);
    text-transform: uppercase;
  }

  &__intro {
    max-width: 56ch;
    color: var(--color-dimmed);
    font-size: 17px;
    line-height: 1.6;
  }

  &__plus-card {
    padding: 20px;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    background: rgb(255 255 255 / 4%);
  }

  &__plus-heading {
    margin-bottom: 12px;
    color: var(--color-primary);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }

  &__plus-list {
    display: flex;
    padding: 0;
    margin: 0;
    list-style: none;
    flex-direction: column;
    gap: 10px;
  }

  &__plus-item {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    font-size: 13px;
    line-height: 1.45;
  }

  &__plus-bullet {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    margin-top: 1px;
    border-radius: 999px;
    color: var(--color-primary);
    background: rgb(242 172 60 / 22%);
    font-size: 11px;
    flex-shrink: 0;
  }

  &__section {
    padding: clamp(24px, 3vw, 40px);
    margin-bottom: 32px;
    border: 1px solid var(--card-border);
    border-radius: 12px;
    background: var(--card-bg);
  }

  &__section-head {
    display: flex;
    align-items: baseline;
    padding-bottom: 16px;
    margin-bottom: 24px;
    border-bottom: 1px dashed var(--card-border);
    gap: 16px;
  }

  &__section-num {
    color: transparent;
    background: linear-gradient(to right, transparent, var(--color-primary));
    font-weight: 500;
    font-size: 32px;
    line-height: 1;
    background-clip: text;
    background-clip: text;
  }

  &__section-title {
    margin: 0;
    font-weight: 600;
    font-size: clamp(20px, 3vw, 28px);
    line-height: 1;
    text-transform: uppercase;
  }

  &__fields {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  &__field {
    display: flex;
    flex-direction: column;
    gap: 6px;

    label {
      font-weight: 500;
      font-size: 14px;
    }

    small {
      color: var(--color-dimmed);
      font-size: 12px;
    }
  }

  &__row-2 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;

    @include media-up(sm) {
      grid-template-columns: 1fr 1fr;
    }
  }

  &__option-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-top: 8px;

    @include media-down(sm) {
      grid-template-columns: 1fr;
    }

    &--col {
      grid-template-columns: 1fr;
    }
  }

  &__option-pill {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    background: rgb(255 255 255 / 2%);
    transition: border-color 150ms ease, background-color 150ms ease;
    gap: 10px;
    cursor: pointer;

    &:hover {
      border-color: rgb(242 172 60 / 60%);
    }

    &[data-checked='true'] {
      border-color: var(--color-primary);
      background: rgb(242 172 60 / 12%);
    }
  }

  &__submit-row {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-top: 8px;

    @include media-up(sm) {
      align-items: center;
      justify-content: space-between;
      flex-direction: row;
    }
  }

  &__success {
    padding: clamp(32px, 4vw, 56px);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    background: var(--card-bg);
    text-align: center;
  }

  &__success-title {
    margin: 0 0 16px;
    font-size: clamp(28px, 5vw, 40px);
    text-transform: uppercase;
  }

  &__success-body {
    max-width: 520px;
    margin: 0 auto 24px;
    color: var(--color-dimmed);
    font-size: 16px;
    line-height: 1.6;
  }
}
</style>
